<?php


namespace WeAreAwesome\FrisbeePHPAPI\Requests\Content;

use GuzzleHttp\Psr7\Response;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentList;
use WeAreAwesome\FrisbeePHPAPI\Exceptions\FrisbeeException;
use WeAreAwesome\FrisbeePHPAPI\Content\ContentResource;

class ListCall implements ContentCall
{
    /**
     * @var string
     */
    protected string $key;
    /**
     * @var int
     */
    protected int $page;
    /**
     * @var int
     */
    protected int $perPage;
    /**
     * @var string
     */
    protected string $orderBy;
    /**
     * @var string
     */
    protected string $orderDirection;

    /**
     * @var array
     */
    protected array $contentTypeIds;

    /**
     * @var int
     */
    protected int $distributionId;

    /**
     * @var Response
     */
    protected Response $response;


    protected array $tags = [];

    protected array $excludeTags = [];


    /**
     * ContentListCall constructor.
     */
    public function __construct(
        string $key = 'content-list',
        array $contentTypeIds = [],
        int $page = 1,
        int $perPage = 100,
        string $orderBy = 'publish_date',
        string $orderDirection = 'asc',
        array $tags  = [],
        array $excludeTags = [],
        ?string $publishedAfter = null,
        ?string $publishedBefore = null
    ) {
        $this->key = $key;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->orderBy = $orderBy;
        $this->orderDirection = $orderDirection;
        $this->contentTypeIds = $contentTypeIds;
        $this->tags = $tags;
        $this->excludeTags = $excludeTags;
        $this->publishedAfter = $publishedAfter;
        $this->publishedBefore = $publishedBefore;

    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return 'get';
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return '/content-list';
    }

    /**
     * @return array[]
     */
    public function getOptions(): array
    {

        return [
            'query' => [
                'distribution_id' => $this->distributionId,
                'content_type_ids' => $this->contentTypeIds,
                'order_by' => $this->orderBy,
                'order_direction' => $this->orderDirection,
                'per_page' => $this->perPage,
                'page' => $this->page,
                'tag_ids' => $this->tags,
                'exclude_tag_ids' => $this->excludeTags,
                'publised_after' => $this->publishedAfter,
                'publised_before' => $this->publishedBefore,
            ]
        ];
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return ContentResource
     */
    public function toContentResource(): ContentResource
    {
        if(!$this->response instanceof Response) {
            throw new FrisbeeException('Called before request handled');
        }

        if($this->response->getStatusCode() !== 200) {
            throw new FrisbeeException('ContentList Call error');
        }

        $data = json_decode($this->response->getBody()->getContents(), true);

        $list =  ContentList::makeFromArray($data['data'], $data['meta']);
        $list->setDataKey($this->getKey());
        return $list;

    }

    /**
     * @param int $distributionId
     */
    public function setDistributionId(int $distributionId)
    {
        $this->distributionId = $distributionId;
    }
}
